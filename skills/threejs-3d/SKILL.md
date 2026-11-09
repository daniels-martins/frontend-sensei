# Three.js & 3D Web Development

You are a 3D web development expert specializing in Three.js, WebGL, and real-time 3D graphics in the browser. Build performant, visually stunning 3D experiences.

## Core Concepts

### Scene Setup

```javascript
import * as THREE from 'three';

// Scene, Camera, Renderer — the holy trinity
const scene = new THREE.Scene();
const camera = new THREE.PerspectiveCamera(
  75,                                    // FOV (degrees)
  window.innerWidth / window.innerHeight, // Aspect ratio
  0.1,                                   // Near clipping plane
  1000                                   // Far clipping plane
);
const renderer = new THREE.WebGLRenderer({
  antialias: true,
  alpha: true,         // Transparent background
  powerPreference: 'high-performance',
});
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2)); // Cap at 2x
renderer.outputColorSpace = THREE.SRGBColorSpace;
renderer.toneMapping = THREE.ACESFilmicToneMapping;
document.body.appendChild(renderer.domElement);

// Responsive handling
window.addEventListener('resize', () => {
  camera.aspect = window.innerWidth / window.innerHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(window.innerWidth, window.innerHeight);
});

// Animation loop
function animate() {
  requestAnimationFrame(animate);
  renderer.render(scene, camera);
}
animate();
```

### Geometry & Materials

```javascript
// Basic mesh: geometry + material = mesh
const geometry = new THREE.BoxGeometry(1, 1, 1);
const material = new THREE.MeshStandardMaterial({
  color: 0x6366f1,
  roughness: 0.4,
  metalness: 0.3,
});
const cube = new THREE.Mesh(geometry, material);
scene.add(cube);

// PBR Materials for realism
const pbrMaterial = new THREE.MeshPhysicalMaterial({
  color: 0xffffff,
  metalness: 0.9,
  roughness: 0.1,
  clearcoat: 1.0,
  clearcoatRoughness: 0.1,
  envMapIntensity: 1.0,
});

// Common geometries
new THREE.SphereGeometry(1, 32, 32);
new THREE.PlaneGeometry(10, 10);
new THREE.CylinderGeometry(0.5, 0.5, 2, 32);
new THREE.TorusKnotGeometry(1, 0.3, 100, 16);
```

### Lighting

```javascript
// Ambient (base illumination)
scene.add(new THREE.AmbientLight(0xffffff, 0.4));

// Directional (sun-like)
const directional = new THREE.DirectionalLight(0xffffff, 1);
directional.position.set(5, 10, 5);
directional.castShadow = true;
directional.shadow.mapSize.set(2048, 2048);
scene.add(directional);

// Point light (bulb)
const point = new THREE.PointLight(0xff6b6b, 1, 10);
point.position.set(2, 3, 2);
scene.add(point);

// Environment map for reflections
import { RGBELoader } from 'three/examples/jsm/loaders/RGBELoader';
new RGBELoader().load('environment.hdr', (texture) => {
  texture.mapping = THREE.EquirectangularReflectionMapping;
  scene.environment = texture;
});
```

### Camera Controls

```javascript
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls';

const controls = new OrbitControls(camera, renderer.domElement);
controls.enableDamping = true;
controls.dampingFactor = 0.05;
controls.maxPolarAngle = Math.PI / 2; // Prevent going below ground
controls.minDistance = 2;
controls.maxDistance = 20;

// In animation loop:
controls.update();
```

## Loading 3D Models

```javascript
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader';
import { DRACOLoader } from 'three/examples/jsm/loaders/DRACOLoader';

const dracoLoader = new DRACOLoader();
dracoLoader.setDecoderPath('/draco/');

const gltfLoader = new GLTFLoader();
gltfLoader.setDRACOLoader(dracoLoader);

gltfLoader.load('/model.glb', (gltf) => {
  const model = gltf.scene;
  model.traverse((child) => {
    if (child.isMesh) {
      child.castShadow = true;
      child.receiveShadow = true;
    }
  });
  scene.add(model);
});
```

## Animation in Three.js

```javascript
// Using Three.js Clock for frame-independent animation
const clock = new THREE.Clock();

function animate() {
  requestAnimationFrame(animate);
  const delta = clock.getDelta();
  const elapsed = clock.getElapsedTime();

  // Rotate
  cube.rotation.y += delta * 0.5;

  // Oscillate
  cube.position.y = Math.sin(elapsed) * 0.5;

  renderer.render(scene, camera);
}

// GSAP integration for complex animations
gsap.to(cube.position, {
  x: 3,
  duration: 2,
  ease: 'power2.inOut',
  yoyo: true,
  repeat: -1,
});

// Skeletal animation from GLTF
let mixer;
gltfLoader.load('/character.glb', (gltf) => {
  mixer = new THREE.AnimationMixer(gltf.scene);
  const action = mixer.clipAction(gltf.animations[0]);
  action.play();
  scene.add(gltf.scene);
});
// In animate(): mixer?.update(delta);
```

## Shaders (Custom Effects)

```javascript
const shaderMaterial = new THREE.ShaderMaterial({
  uniforms: {
    uTime: { value: 0 },
    uColor: { value: new THREE.Color(0x6366f1) },
  },
  vertexShader: `
    varying vec2 vUv;
    uniform float uTime;
    void main() {
      vUv = uv;
      vec3 pos = position;
      pos.z += sin(pos.x * 3.0 + uTime) * 0.2;
      gl_Position = projectionMatrix * modelViewMatrix * vec4(pos, 1.0);
    }
  `,
  fragmentShader: `
    varying vec2 vUv;
    uniform vec3 uColor;
    void main() {
      gl_FragColor = vec4(uColor * vUv.y, 1.0);
    }
  `,
});

// Update in loop:
shaderMaterial.uniforms.uTime.value = elapsed;
```

## React Three Fiber

```jsx
import { Canvas, useFrame } from '@react-three/fiber';
import { OrbitControls, Environment } from '@react-three/drei';
import { useRef } from 'react';

function SpinningBox() {
  const meshRef = useRef();
  useFrame((state, delta) => {
    meshRef.current.rotation.y += delta * 0.5;
  });

  return (
    <mesh ref={meshRef}>
      <boxGeometry args={[1, 1, 1]} />
      <meshStandardMaterial color="#6366f1" roughness={0.4} />
    </mesh>
  );
}

export default function Scene() {
  return (
    <Canvas camera={{ position: [3, 3, 3], fov: 50 }}>
      <ambientLight intensity={0.4} />
      <directionalLight position={[5, 10, 5]} />
      <SpinningBox />
      <OrbitControls enableDamping />
      <Environment preset="sunset" />
    </Canvas>
  );
}
```

## Performance Optimization

- **Pixel ratio**: Cap at `Math.min(window.devicePixelRatio, 2)`.
- **Geometry**: Use `BufferGeometry`, reduce polygon count, use LOD (Level of Detail).
- **Textures**: Power-of-two dimensions (512, 1024, 2048). Compress with KTX2/Basis. Dispose unused textures.
- **Draw calls**: Merge geometries with `BufferGeometryUtils.mergeGeometries()`. Use instancing for repeated objects.
- **Shadows**: Limit shadow-casting lights. Use smaller shadow maps where possible.
- **Frustum culling**: Enabled by default. Don't render what the camera can't see.
- **Dispose resources**: When removing objects, call `geometry.dispose()`, `material.dispose()`, `texture.dispose()`.

```javascript
// Instanced mesh for 1000 identical objects
const instancedMesh = new THREE.InstancedMesh(geometry, material, 1000);
const dummy = new THREE.Object3D();
for (let i = 0; i < 1000; i++) {
  dummy.position.set(Math.random() * 50 - 25, 0, Math.random() * 50 - 25);
  dummy.updateMatrix();
  instancedMesh.setMatrixAt(i, dummy.matrix);
}
scene.add(instancedMesh);
```

## Common Anti-Patterns

- Creating new objects (Vector3, Color) inside the render loop — reuse them.
- Not disposing geometries/materials/textures when removing objects — memory leak.
- Using `MeshBasicMaterial` when lighting is present — it ignores lights.
- Not capping pixel ratio — 3x or 4x destroys mobile GPU performance.
- Loading uncompressed models — always use Draco or meshopt compression.
- Animating with `setInterval` — use `requestAnimationFrame` or GSAP.
- Not handling resize — scene stretches or crops on window resize.
